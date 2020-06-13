pipeline {
    agent {
        label 'builder'
    }
    environment {
        GITHUB_ORGANIZATION='oat-sa'
        REPO_NAME='oat-sa/extension-tao-delivery-rdf'
    }
    stages {
        stage('Resolve TAO dependencies') {

            steps {
                sh(
                    label : 'Create build build directory',
                    script: 'mkdir -p build'
                )

                withCredentials([string(credentialsId: 'jenkins_github_token', variable: 'GIT_TOKEN')]) {
                    sh(
                        label : 'Run the Dependency Resolver',
                        script: '''
changeBranch=$CHANGE_BRANCH
TEST_BRANCH="${changeBranch:-$BRANCH_NAME}"
echo "select branch : ${TEST_BRANCH}"
docker run --rm  \\
-e "GITHUB_ORGANIZATION=${GITHUB_ORGANIZATION}" \\
-e "GITHUB_SECRET=${GIT_TOKEN}"  \\
tao/dependency-resolver oat:dependencies:resolve --main-branch ${TEST_BRANCH} --repository-name ${REPO_NAME} > build/composer.json
                        '''
                    )
                }
            }
        }
        stage('Install') {
            agent {
                docker {
                    image 'alexwijn/docker-git-php-composer'
                    reuseNode true
                }
            }
            environment {
                HOME = '.'
                REPO_NAME = ${env.REPO_NAME}
                TEST_BRANCH = ${env.TEST_BRANCH}
            }
            options {
                skipDefaultCheckout()
            }
            steps {
                dir('build') {
                    sh(
                        label: 'Packagist Branch Check',
                        script: '''
                            php -r '/* Pingigist! */ $maxAttempts = 5; $waitingTime = 30; $repoName = "${REPO_NAME}"; $testBranch = "${TEST_BRANCH}"; $packagistBranch = "dev-$testBranch"; $packagistPayloadUrl = "https://repo.packagist.org/p/$repoName.json"; echo "Waiting for packagist to acknowledge branch $testBranch on repository $repoName ($maxAttempts attempts) ...\n"; for ($i = 0; $i < 5; $i++) { if ($i > 0) { echo "Waiting $waitingTime seconds to the next attempt...\n"; sleep($waitingTime); } $attempt = $i + 1; echo "Attempt #$attempt for branch $testBranch on repository $repoName...\n"; if (false === ($packagistPayload = @file_get_contents($packagistPayloadUrl))) { echo "Packagist payload could not be retrieved from $packagistPayloadUrl.\n"; exit(1); } if (!($packagistMetadata = @json_decode($packagistPayload, true))) { echo "Packagist payload could not be parsed.\n"; exit(2); } if (empty($packagistMetadata["packages"]) || empty($packagistMetadata["packages"][$repoName])) { echo "Packagist metadata is not properly formated.\n"; exit(3); } if (array_key_exists($packagistBranch, $packagistMetadata["packages"][$repoName])) { echo "Branch $testBranch exists on packagist!\n"; exit(0); } } echo "Branch $testBranch does not exist on packagist for repository $repoName.\n"; exit(4);'
                        '''
                    )
                    sh(
                        label: 'Install/Update sources from Composer',
                        script: 'COMPOSER_DISCARD_CHANGES=true composer update --prefer-source --no-interaction --no-ansi --no-progress --no-scripts'
                    )
                    sh(
                        label: 'Add phpunit',
                        script: 'composer require phpunit/phpunit:^8.5'
                    )
                    sh(
                        label: "Extra filesystem mocks",
                        script: '''
mkdir -p taoQtiItem/views/js/mathjax/ && touch taoQtiItem/views/js/mathjax/MathJax.js
mkdir -p tao/views/locales/en-US/
    echo "{\\"serial\\":\\"${BUILD_ID}\\",\\"date\\":$(date +%s),\\"version\\":\\"3.3.0-${BUILD_NUMBER}\\",\\"translations\\":{}}" > tao/views/locales/en-US/messages.json
mkdir -p tao/views/locales/en-US/
                        '''
                    )
                }
            }
        }
        stage('Tests') {
            parallel {
                stage('Backend Tests') {
                    when {
                        expression {
                            fileExists('build/taoDeliveryRdf/test/unit')
                        }
                    }
                    agent {
                        docker {
                            image 'alexwijn/docker-git-php-composer'
                            reuseNode true
                        }
                    }
                    options {
                        skipDefaultCheckout()
                    }
                    steps {
                        dir('build'){
                            sh(
                                label: 'Run backend tests',
                                script: './vendor/bin/phpunit taoDeliveryRdf/test/unit'
                            )
                        }
                    }
                }
                stage('Frontend Tests') {
                    when {
                        expression {
                            fileExists('build/taoDeliveryRdf/views/build/grunt/test.js')
                        }
                    }
                    agent {
                        docker {
                            image 'btamas/puppeteer-git'
                            reuseNode true
                        }
                    }
                    environment {
                        HOME = '.'
                    }
                    options {
                        skipDefaultCheckout()
                    }
                    steps {
                        dir('build/tao/views/build') {
                            sh(
                                label: 'Install tao-core frontend extensions',
                                script: 'npm install'
                            )
                            sh (
                                label : 'Run frontend tests',
                                script: 'npx grunt connect:test taodeliveryrdftest'
                            )
                        }
                    }
                }
            }
        }
    }
    post {
        always {
            cleanWs disableDeferredWipeout: true
        }
    }
}
